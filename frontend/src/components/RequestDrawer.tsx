import React, { useState } from 'react';
import { X, Clock, FileText, Send, AlertCircle } from 'lucide-react';
import { useRequests } from '../hooks/useRequests';

interface RequestDrawerProps {
  isOpen: boolean;
  onClose: () => void;
  type: 'dispute' | 'overtime' | 'leave';
  attendanceId?: number;
}

export const RequestDrawer = ({ isOpen, onClose, type, attendanceId }: RequestDrawerProps) => {
  const { fileDispute, fileOvertime, fileLeave } = useRequests();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    proposed_in: '',
    proposed_out: '',
    reason: '',
    date: '',
    start_time: '',
    end_time: '',
    leave_type: 'Sick Leave',
    start_date: '',
    end_date: ''
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (type === 'dispute' && attendanceId) {
        await fileDispute({
          attendance_id: attendanceId,
          proposed_time_in: formData.proposed_in,
          proposed_time_out: formData.proposed_out,
          reason: formData.reason
        });
      } else if (type === 'overtime') {
        await fileOvertime({
          date: formData.date,
          start_time: formData.start_time,
          end_time: formData.end_time,
          reason: formData.reason
        });
      } else if (type === 'leave') {
        await fileLeave({
          leave_type: formData.leave_type,
          start_date: formData.start_date,
          end_date: formData.end_date,
          reason: formData.reason
        });
      }
      alert('Request submitted successfully!');
      onClose();
    } catch (err) {
      alert('Failed to submit request.');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={onClose} />
      
      <div className="absolute inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col animate-slide-in-right">
        {/* Header */}
        <div className="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 className="text-lg font-bold text-slate-800 capitalize">File {type}</h2>
            <p className="text-xs text-slate-500 mt-0.5">Please provide the details below.</p>
          </div>
          <button onClick={onClose} className="p-2 hover:bg-white rounded-lg transition-colors border border-transparent hover:border-slate-200">
            <X size={20} className="text-slate-400" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-6">
          {type === 'dispute' && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Proposed In</label>
                  <div className="relative">
                    <Clock size={16} className="absolute left-3 top-3 text-slate-400" />
                    <input 
                      type="time" 
                      required
                      className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                      value={formData.proposed_in}
                      onChange={e => setFormData({...formData, proposed_in: e.target.value})}
                    />
                  </div>
                </div>
                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Proposed Out</label>
                  <div className="relative">
                    <Clock size={16} className="absolute left-3 top-3 text-slate-400" />
                    <input 
                      type="time" 
                      required
                      className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                      value={formData.proposed_out}
                      onChange={e => setFormData({...formData, proposed_out: e.target.value})}
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Common Reason Field */}
          <div className="space-y-1.5">
            <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Reason / Justification</label>
            <div className="relative">
              <FileText size={16} className="absolute left-3 top-3 text-slate-400" />
              <textarea 
                required
                rows={4}
                placeholder="Briefly explain why you're filing this request..."
                className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all resize-none"
                value={formData.reason}
                onChange={e => setFormData({...formData, reason: e.target.value})}
              />
            </div>
          </div>

          <div className="p-4 bg-amber-50 rounded-lg border border-amber-100 flex gap-3">
            <AlertCircle size={18} className="text-amber-500 shrink-0 mt-0.5" />
            <p className="text-[11px] text-amber-700 leading-relaxed">
              Requests are subject to Coach endorsement and Admin approval. Once approved, your attendance log will be updated accordingly.
            </p>
          </div>
        </form>

        {/* Footer */}
        <div className="p-6 border-t border-slate-100 bg-slate-50/50 flex gap-3">
          <button 
            type="button"
            onClick={onClose}
            className="flex-1 px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors"
          >
            Cancel
          </button>
          <button 
            type="submit"
            disabled={loading}
            onClick={handleSubmit}
            className="flex-1 px-4 py-2.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary/90 transition-all flex items-center justify-center gap-2 shadow-lg shadow-primary/20 disabled:opacity-50"
          >
            {loading ? 'Submitting...' : (
              <>
                <Send size={16} />
                Submit Request
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};
