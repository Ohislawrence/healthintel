import React, { useState, useRef, useEffect } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import api from '../../lib/api';

export default function ResultChatPanel({ submissionId, onClose }) {
    const [message, setMessage] = useState('');
    const [convId, setConvId] = useState(null);
    const bottomRef = useRef(null);
    const inputRef = useRef(null);

    const { data: convData, refetch } = useQuery({
        queryKey: ['conversation', convId],
        queryFn: () => api.get(`/conversations/${convId}`),
        enabled: !!convId,
    });
    const messages = convData?.data?.conversation?.messages || [];

    const startConv = useMutation({
        mutationFn: (msg) => api.post('/conversations', { lab_submission_id: submissionId, initial_message: msg }),
        onSuccess: (d) => {
            setConvId(d?.data?.conversation?.id);
            inputRef.current?.focus();
        },
    });

    const sendMsg = useMutation({
        mutationFn: (msg) => api.post(`/conversations/${convId}/message`, { message: msg }),
        onSuccess: () => refetch(),
    });

    const handleSend = () => {
        if (!message.trim()) return;
        if (!convId) startConv.mutate(message.trim());
        else sendMsg.mutate(message.trim());
        setMessage('');
    };

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
        inputRef.current?.focus();
    }, [messages]);

    return (
        <>
            {/* Dark overlay - tap to close */}
            <div
                className="fixed inset-0 z-[100] bg-black/50 transition-opacity"
                onClick={onClose}
                aria-hidden="true"
            />

            {/* Chat panel - slides in from right, fullscreen on mobile */}
            <div className="fixed inset-y-0 right-0 z-[110] w-full sm:max-w-md bg-white shadow-2xl flex flex-col animate-slide-in">
                {/* Header */}
                <div className="flex items-center justify-between px-4 py-3 border-b bg-white shrink-0">
                    <h3 className="font-bold text-sm text-gray-900">💬 Ask about these results</h3>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                        aria-label="Close chat"
                    >
                        &times;
                    </button>
                </div>

                {/* Messages */}
                <div className="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">
                    {!convId && !startConv.isPending && (
                        <div className="text-center py-8">
                            <span className="text-3xl block mb-3">💬</span>
                            <p className="text-sm text-gray-500">Ask a question about your lab results</p>
                            <p className="text-xs text-gray-400 mt-1">Example: "What does my glucose level mean?"</p>
                        </div>
                    )}
                    {startConv.isPending && (
                        <div className="flex justify-start">
                            <div className="bg-white px-4 py-3 rounded-2xl rounded-bl-md shadow-sm text-sm text-gray-400">
                                Starting conversation...
                            </div>
                        </div>
                    )}
                    {messages.map((m, i) => (
                        <div key={i} className={`flex ${m.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                            <div
                                className={`max-w-[82%] px-4 py-3 rounded-2xl text-sm leading-relaxed ${
                                    m.role === 'user'
                                        ? 'bg-teal-600 text-white rounded-br-md shadow-sm'
                                        : 'bg-white text-gray-800 rounded-bl-md shadow-sm border border-gray-100'
                                }`}
                            >
                                {m.content}
                            </div>
                        </div>
                    ))}
                    {sendMsg.isPending && (
                        <div className="flex justify-start">
                            <div className="bg-white px-4 py-3 rounded-2xl rounded-bl-md shadow-sm text-sm text-gray-400 border border-gray-100">
                                Typing...
                            </div>
                        </div>
                    )}
                    <div ref={bottomRef} className="h-1" />
                </div>

                {/* Input bar */}
                <div className="p-3 border-t bg-white shrink-0 flex gap-2">
                    <input
                        ref={inputRef}
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSend()}
                        placeholder="Ask about your results..."
                        className="flex-1 text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 focus:outline-none focus:border-teal-400 focus:bg-white transition-colors"
                        disabled={startConv.isPending || sendMsg.isPending}
                        autoFocus
                    />
                    <button
                        onClick={handleSend}
                        disabled={!message.trim() || startConv.isPending || sendMsg.isPending}
                        className="bg-teal-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed hover:bg-teal-700 transition-colors flex-shrink-0"
                    >
                        Send
                    </button>
                </div>
            </div>

            <style>{`
                @keyframes slideIn {
                    from { transform: translateX(100%); }
                    to { transform: translateX(0); }
                }
                .animate-slide-in {
                    animation: slideIn 0.2s ease-out;
                }
            `}</style>
        </>
    );
}