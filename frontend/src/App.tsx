import { useAttendance } from './hooks/useAttendance';
import { AppShell } from './components/AppShell';
import { cn } from './lib/utils';

const StatusPill = ({ status }: { status: string }) => {
  const getStatusStyles = (s: string) => {
    switch (s.toLowerCase()) {
      case 'present':
      case 'approved':
        return 'bg-emerald-50 text-emerald-700 border-emerald-100';
      case 'late':
      case 'pending':
        return 'bg-amber-50 text-amber-700 border-amber-100';
      case 'absent':
      case 'denied':
        return 'bg-rose-50 text-rose-700 border-rose-100';
      default:
        return 'bg-slate-50 text-slate-700 border-slate-100';
    }
  };

  return (
    <span className={cn(
      "px-2.5 py-1 rounded-full text-[11px] font-bold border uppercase tracking-wider",
      getStatusStyles(status)
    )}>
      {status}
    </span>
  );
};

function App() {
  const { data, loading, error } = useAttendance();

  return (
    <AppShell roleId={1}>
      <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/50 border-bottom border-slate-200">
                <th className="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date</th>
                <th className="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Time In</th>
                <th className="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Time Out</th>
                <th className="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Hours</th>
                <th className="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                <th className="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr><td colSpan={6} className="px-6 py-8 text-center text-slate-400">Loading logs...</td></tr>
              ) : error ? (
                <tr><td colSpan={6} className="px-6 py-8 text-center text-rose-500 font-medium">{error}</td></tr>
              ) : data.map((record, i) => (
                <tr key={i} className="hover:bg-slate-50/50 transition-colors group">
                  <td className="px-6 py-4 text-sm font-medium text-slate-700">{record.date}</td>
                  <td className="px-6 py-4 text-sm text-slate-600">{record.time_in}</td>
                  <td className="px-6 py-4 text-sm text-slate-600">{record.time_out}</td>
                  <td className="px-6 py-4 text-sm font-mono text-slate-500">{record.total_hours}h</td>
                  <td className="px-6 py-4"><StatusPill status={record.status} /></td>
                  <td className="px-6 py-4 text-right">
                    <button className="text-primary text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity hover:underline">
                      File Dispute
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppShell>
  );
}

export default App;
