import { useState } from 'react';
import { Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface AddTodoDialogProps {
  onAdd: (title: string, description: string, stepTitles: string[]) => void;
  disabled?: boolean;
}

export const AddTodoDialog = ({ onAdd, disabled = false }: AddTodoDialogProps) => {
  const [open, setOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [steps, setSteps] = useState<string[]>(['']);

  const handleAddStep = () => {
    setSteps([...steps, '']);
  };

  const handleRemoveStep = (index: number) => {
    setSteps(steps.filter((_, i) => i !== index));
  };

  const handleStepChange = (index: number, value: string) => {
    const newSteps = [...steps];
    newSteps[index] = value;
    setSteps(newSteps);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (title.trim() && steps.some((s) => s.trim())) {
      onAdd(
        title,
        description,
        steps.filter((s) => s.trim())
      );
      setTitle('');
      setDescription('');
      setSteps(['']);
      setOpen(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button disabled={disabled} size="lg" className="gap-2">
          <Plus className="w-5 h-5" />
          <span className="hidden sm:inline">Add New Todo</span>
          <span className="sm:hidden">New Todo</span>
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Create New Todo</DialogTitle>
          <DialogDescription>
            Add a new todo and define its processing steps
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="title">Todo Title</Label>
            <Input
              id="title"
              placeholder="e.g., Implement API endpoint"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              placeholder="Add details about this todo..."
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="resize-none"
              rows={3}
            />
          </div>

          <div className="space-y-3">
            <Label>Processing Steps</Label>
            {steps.map((step, index) => (
              <div key={index} className="flex gap-2">
                <Input
                  placeholder={`Step ${index + 1}`}
                  value={step}
                  onChange={(e) => handleStepChange(index, e.target.value)}
                />
                {steps.length > 1 && (
                  <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={() => handleRemoveStep(index)}
                  >
                    <X className="w-4 h-4" />
                  </Button>
                )}
              </div>
            ))}
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={handleAddStep}
              className="w-full"
            >
              <Plus className="w-4 h-4 mr-2" />
              Add Step
            </Button>
          </div>

          <div className="flex gap-3 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => setOpen(false)}
              className="flex-1"
            >
              Cancel
            </Button>
            <Button
              type="submit"
              className="flex-1"
              disabled={!title.trim() || !steps.some((s) => s.trim())}
            >
              Create Todo
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};
