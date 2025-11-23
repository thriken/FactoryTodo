import { CheckCircle2, Circle, Clock } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ProcessingStep, StepStatus } from '@/hooks/useTodoStore';

interface ProcessingStepsProps {
  steps: ProcessingStep[];
  todoId: string;
  canEdit: boolean;
  onStepStatusChange: (stepId: string, newStatus: StepStatus) => void;
}

const getStatusColor = (status: StepStatus) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-700 border-green-300';
    case 'in-progress':
      return 'bg-blue-100 text-blue-700 border-blue-300';
    case 'pending':
      return 'bg-slate-100 text-slate-700 border-slate-300';
  }
};

const getStatusIcon = (status: StepStatus) => {
  switch (status) {
    case 'completed':
      return <CheckCircle2 className="w-5 h-5 text-green-600" />;
    case 'in-progress':
      return <Clock className="w-5 h-5 text-blue-600" />;
    case 'pending':
      return <Circle className="w-5 h-5 text-slate-400" />;
  }
};

export const ProcessingSteps = ({
  steps,
  todoId,
  canEdit,
  onStepStatusChange,
}: ProcessingStepsProps) => {
  if (steps.length === 0) {
    return null;
  }

  const completedCount = steps.filter((s) => s.status === 'completed').length;
  const progressPercentage = Math.round((completedCount / steps.length) * 100);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="font-semibold text-slate-900">Processing Steps</h3>
        <Badge variant="outline" className="bg-slate-50">
          {completedCount}/{steps.length} completed
        </Badge>
      </div>

      <div className="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
        <div
          className="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-300"
          style={{ width: `${progressPercentage}%` }}
        />
      </div>

      <div className="space-y-3">
        {steps.map((step, index) => (
          <div key={step.id}>
            <div className="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors">
              <div className="flex-shrink-0">{getStatusIcon(step.status)}</div>

              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                  <p className="font-medium text-slate-900 truncate">
                    {index + 1}. {step.title}
                  </p>
                  <Badge className={`text-xs font-medium border ${getStatusColor(step.status)}`}>
                    {step.status === 'completed'
                      ? 'Completed'
                      : step.status === 'in-progress'
                        ? 'In Progress'
                        : 'Pending'}
                  </Badge>
                </div>
                {step.completedBy && step.completedAt && (
                  <p className="text-xs text-slate-500 mt-1">
                    Completed by {step.completedBy} on{' '}
                    {new Date(step.completedAt).toLocaleDateString()}
                  </p>
                )}
              </div>

              {canEdit && step.status !== 'completed' && (
                <Select
                  value={step.status}
                  onValueChange={(newStatus: StepStatus) =>
                    onStepStatusChange(step.id, newStatus)
                  }
                >
                  <SelectTrigger className="w-32 bg-white border-slate-200">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="in-progress">In Progress</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                  </SelectContent>
                </Select>
              )}
            </div>

            {index < steps.length - 1 && (
              <div className="h-3 flex justify-center">
                <div className="w-1 bg-gradient-to-b from-slate-300 to-slate-200" />
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};
