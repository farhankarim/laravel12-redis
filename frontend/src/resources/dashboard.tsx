import { useEffect, useState } from 'react';
import { Card, CardContent, Typography, Box, CircularProgress, Grid } from '@mui/material';
import { Title } from 'react-admin';

interface QueueStats {
  waiting: number;
  active: number;
  delayed: number;
  failed: number;
  completed: number;
}

interface UserStats {
  total: number;
  verified: number;
  unverified: number;
}

function StatCard({ label, value, color = 'primary.main' }: { label: string; value: number | string; color?: string }) {
  return (
    <Card>
      <CardContent>
        <Typography variant="h4" sx={{ color, fontWeight: 'bold' }}>
          {value}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          {label}
        </Typography>
      </CardContent>
    </Card>
  );
}

export const Dashboard = () => {
  const [queueStats, setQueueStats] = useState<QueueStats | null>(null);
  const [userStats, setUserStats] = useState<UserStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const headers = { Authorization: `Bearer ${token}` };

    Promise.all([
      fetch('/api/dashboard/queue', { headers }).then((r) => r.json()),
      fetch('/api/dashboard/users', { headers }).then((r) => r.json()),
    ])
      .then(([queue, users]) => {
        setQueueStats(queue);
        setUserStats(users);
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <Box sx={{ p: 4 }}><CircularProgress /></Box>;

  return (
    <Box sx={{ p: 2 }}>
      <Title title="Dashboard" />

      <Typography variant="h5" sx={{ mb: 2 }}>
        👥 User Statistics
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid size={{ xs: 12, sm: 4 }}>
          <StatCard label="Total Users" value={userStats?.total ?? '—'} color="primary.main" />
        </Grid>
        <Grid size={{ xs: 12, sm: 4 }}>
          <StatCard label="Verified" value={userStats?.verified ?? '—'} color="success.main" />
        </Grid>
        <Grid size={{ xs: 12, sm: 4 }}>
          <StatCard label="Unverified" value={userStats?.unverified ?? '—'} color="warning.main" />
        </Grid>
      </Grid>

      <Typography variant="h5" sx={{ mb: 2 }}>
        ⚙️ Queue Statistics
      </Typography>
      <Grid container spacing={2}>
        <Grid size={{ xs: 6, sm: 4, md: 2 }}>
          <StatCard label="Waiting" value={queueStats?.waiting ?? '—'} color="info.main" />
        </Grid>
        <Grid size={{ xs: 6, sm: 4, md: 2 }}>
          <StatCard label="Active" value={queueStats?.active ?? '—'} color="primary.main" />
        </Grid>
        <Grid size={{ xs: 6, sm: 4, md: 2 }}>
          <StatCard label="Delayed" value={queueStats?.delayed ?? '—'} color="warning.main" />
        </Grid>
        <Grid size={{ xs: 6, sm: 4, md: 2 }}>
          <StatCard label="Failed" value={queueStats?.failed ?? '—'} color="error.main" />
        </Grid>
        <Grid size={{ xs: 6, sm: 4, md: 2 }}>
          <StatCard label="Completed" value={queueStats?.completed ?? '—'} color="success.main" />
        </Grid>
      </Grid>
    </Box>
  );
};
