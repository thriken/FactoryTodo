import { Trash2, Calendar, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ProcessingSteps } from './ProcessingSteps';
import { Todo, StepStatus, UserRole } from '@/hooks/useTodoStore';

interface TodoCardProps {
  todo: Todo;
  canEdit: boolean;
  currentRole: UserRole;
  onUpdateStep: (stepId: string, newStatus: StepStatus) => void;
  onDelete: (todoId: string) => void;
}

const getStatusColor = (status: Todo['status']) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-700 border-green-300';
    case 'in-progress':
      return 'bg-blue-100 text-blue-700 border-blue-300';
    case 'pending':
      return 'bg-amber-100 text-amber-700 border-amber-300';
  }
};

const getStatusLabel = (status: Todo['status']) => {
  return status.charAt(0).toUpperCase() + status.slice(1).replace('-', ' ');
};

export const TodoCard = ({
  todo,
  canEdit,
  currentRole,
  onUpdateStep,
  onDelete,
}: TodoCardProps) => {
  return (
    <Card className="overflow-hidden hover:shadow-md transition-shadow border-slate-200">
      <div className="p-6 space-y-4">
        <div className="flex items-start justify-between gap-4">
          <div className="flex-1 min-w-0">
            <h3 className="text-lg font-semibold text-slate-900 truncate">{todo.title}</h3>
            <p className="text-sm text-slate-600 mt-1 line-clamp-2">{todo.description}</p>
          </div>
          {canEdit && (
            <Button
              variant="ghost"
              size="icon"
              onClick={() => onDelete(todo.id)}
              className="flex-shrink-0 text-slate-500 hover:text-red-600"
            >
              <Trash2 className="w-4 h-4" />
            </Button>
          )}
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Badge className={`text-xs font-medium border ${getStatusColor(todo.status)}`}>
            {getStatusLabel(todo.status)}
          </Badge>
          <div className="flex items-center gap-1 text-xs text-slate-600">
            <User className="w-3 h-3" />
            {todo.createdBy}
          </div>
          <div className="flex items-center gap-1 text-xs text-slate-600">
            <Calendar className="w-3 h-3" />
            {new Date(todo.createdAt).toLocaleDateString()}
          </div>
        </div>

        <ProcessingSteps
          steps={todo.steps}
          todoId={todo.id}
          canEdit={canEdit}
          onStepStatusChange={onUpdateStep}
        />
      </div>
    </Card>
  );
};
