<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditRequest;
use App\Http\Requests\MessageRequest;
use App\Http\Requests\SendRequest;
use App\Http\Requests\ShowRequest;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

time::Timezone(3.5);

class ChatController extends Controller {

    public function Create( Request $request ) {
        $user = Auth::user();

        if ( !$user ) {
            return response()->json([ 'error' => 'کاربر وارد نشده است.' ], 401);
        }

        $username = strtolower($request->username);

        if ( $user->username === $username ) {
            return response()->json([ 'error' => 'میخوای با خودت حرف بزنی؟ کثخلی چیزی هستی؟' ], 401);
        }

        $UserExists = User::where('username', $username)->first();
        if ( !$UserExists ) {
            return response()->json([ 'error' => 'این نام کاربری در سیستم وجود ندارد.' ], 404);
        }

        if ( Chat::where('target_id', $UserExists->id)->where('user_id', $user->id)->exists() ) {
            return response()->json([ 'error' => 'این مکالمه باز هست.' ], 409);
        }

        Chat::create([
            'user_id'   => $UserExists->id,
            'target_id' => $user->id,
        ]);

        $chat = Chat::create([
            'user_id'   => $user->id,
            'target_id' => $UserExists->id,
        ]);

        $chat->load('target');

        return response()->json([
            'success' => 'کاربر با موفقیت ایجاد شد',
            'hash'    => $chat->target->hash,
        ], 200);
    }

    public function GetChats() {
        $user = Auth::user()->load([
            'chats' => function ( $query ) {
                $query->orderBy('updated_at', 'desc')->with('target');
            },
        ]);

        $lastChat = $user->targets->last();

        $processedChats = $user->targets->map(function ( $chat ) use ( $user ) {
            $receiver = $chat->user->hash;
            $hash     = $user->hash;

            $lastMessage   = Message::where(function ( $query ) use ( $hash, $receiver ) {
                $query->Where([
                    'sender'   => $receiver,
                    'receiver' => $hash,
                ]);
            })->latest()->first();
            $formattedDate = $lastMessage ? time::format(strtotime($lastMessage->created_at), 'W D M H:i:s') : null;

            return [
                'username'     => $chat->user->username,
                'hash'         => $receiver,
                'last_message' => [
                    'text'       => $lastMessage->text ?? "",
                    'created_at' => $formattedDate ?? "اخیرا",
                ],
            ];
        })->sortByDesc('updated_at')->values()->all();

        return response()->json([
            'chats'    => $processedChats,
            'lastChat' => $lastChat ? [
                'username' => $lastChat->user->username,
                'hash'     => $lastChat->user->hash,
            ] : null,
            'user'     => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
            ],
        ]);
    }

    public function show( ShowRequest $request ) {
        $user     = Auth::user();
        $hash     = $user->hash;
        $receiver = $request->receiver;

        $messages = Message::where(function ( $query ) use ( $hash ) {
            $query->where('sender', $hash)->orwhere('receiver', $hash);
        })->Where(function ( $query ) use ( $receiver ) {
            $query->where('receiver', $receiver)->orwhere('sender', $receiver);
        })->get()->map(function ( $message ) use ( $hash ) {
            if ( $message->receiver == $hash ) {
                $message->status = 1;
                $message->save();
            }

            $formattedDate = time::format(strtotime($message->created_at), 'W D M H:i:s');

            return [
                'id'         => $message->id,
                'sender'     => $message->sender,
                'text'       => $message->text,
                'status'     => (int) $message->status,
                'created_at' => $formattedDate,
            ];
        });

        $user->load([
            'chats' => function ( $query ) {
                $query->orderBy('updated_at', 'desc')->with('target')->update([ 'updated_at' => Carbon::now() ]);
            },
        ]);
        $user = User::where('hash', $receiver)->first([ 'id', 'username', 'name' ]);
        return response()->json([
            'user'  => $user,
            'chats' => $messages,
        ]);
    }

    public function send( SendRequest $request ) {
        $user = Auth::user();
        $hash = $user->hash;

        $message       = Message::create([
            'sender'   => $hash,
            'receiver' => $request['receiver'],
            'text'     => $request['text'],
        ]);
        $formattedDate = time::format(strtotime($message->created_at), 'H:i:s');

        return response()->json([
            'success'    => true,
            'id'         => $message->id,
            'sender'     => $hash,
            'created_at' => $formattedDate,
        ]);
    }

    public function edit( EditRequest $request ) {
        $hash = Auth::user()->hash;

        $message = Message::where('id', $request->id)->where('sender', $hash)->first();

        if ( !$message ) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found or you do not have permission to edit it.',
            ], 404);
        }

        $message->text   = $request->text;
        $message->status = 2;
        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully!',
        ]);
    }

    public function load( MessageRequest $request ) {
        $hash     = Auth::user()->hash;
        $receiver = $request->receiver;
        $messages = Message::where('receiver', $hash)->where('sender', $receiver)->whereIn('status', [ 0, 2, ])->get();

        if ( $messages->isNotEmpty() ) {
            Message::where('receiver', $hash)->where('sender', $receiver)->whereIn('status', [
                0,
                2,
            ])->update([ 'status' => 1 ]);
        }

        $formattedMessages = $messages->map(function ( $message ) {
            $formattedDate = date('H:i:s', strtotime($message->created_at));

            return [
                'id'         => $message->id,
                'sender'     => $message->sender,
                'text'       => $message->text,
                'created_at' => $formattedDate,
                'status'     => (int) $message->status == 2 ? 2 : 5,
            ];
        });

        if ( $formattedMessages->isEmpty() ) {
            $hasSenderMessages = Message::where('sender', $hash)->where('receiver', $receiver)->whereIn('status', [
                0,
                2,
            ])->exists();

            if ( $hasSenderMessages ) {
                $formattedMessages = [ 'X' ];
            }
        }

        return response()->json($formattedMessages);
    }

    protected function respondWithToken( $token ) {
        return response()->json([
            'token' => $token,
            'user'  => Auth::user(),

        ]);
    }

    public function guard() {
        return Auth::guard('api');
    }

}
