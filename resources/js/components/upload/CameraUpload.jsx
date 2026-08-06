import React, { useRef, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import api from '../../lib/api';

export default function CameraUpload({ onDraft, onError }) {
    const fileRef = useRef(null);
    const [preview, setPreview] = useState(null);
    const [fileName, setFileName] = useState('');

    const uploadMutation = useMutation({
        mutationFn: (base64) => api.post('/submissions/image', { image_base64: base64, image_name: fileName }),
        onSuccess: (d) => onDraft?.(d?.data),
        onError: (e) => onError?.(e?.message || 'Upload failed'),
    });

    const handleFile = (file) => {
        if (!file) return;
        setFileName(file.name);
        const reader = new FileReader();
        reader.onload = () => { setPreview(reader.result); uploadMutation.mutate(reader.result.split(',')[1] || reader.result); };
        reader.readAsDataURL(file);
    };

    return (
        <div className="card p-4">
            <div className="flex items-center gap-3 mb-3">
                <span className="text-2xl">📸</span>
                <div>
                    <h3 className="text-sm font-bold">Upload lab report photo</h3>
                    <p className="text-xs text-neutral-400">Take a clear photo of your lab report</p>
                </div>
            </div>
            <input ref={fileRef} type="file" accept="image/*" capture="environment" onChange={e => handleFile(e.target.files[0])} className="hidden" />
            <button onClick={() => fileRef.current?.click()} disabled={uploadMutation.isPending} className="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-teal-300 hover:text-teal-600 transition-colors disabled:opacity-50">
                {uploadMutation.isPending ? '⏳ Processing image...' : '📷 Take photo or choose file'}
            </button>
            {uploadMutation.isError && <p className="text-xs text-red-500 mt-2">{uploadMutation.error?.message || 'Upload failed'}</p>}
            {preview && <img src={preview} alt="Preview" className="mt-3 rounded-lg max-h-48 object-cover w-full" />}
        </div>
    );
}