import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-lab-revision-history',
  templateUrl: './revision-history.component.html',
  styleUrls: ['./revision-history.component.css'],
})
export class RevisionHistoryComponent implements OnInit {
  rows: any[] = [];
  loading = false;
  expandedId: number | null = null;
  payloadObj: Record<number, unknown> = {};

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.service.get('qc/lab.php?type=getLabsRevisionHistory').subscribe({
      next: (res: any) => {
        this.rows = Array.isArray(res) ? res : [];
        this.loading = false;
      },
      error: () => {
        this.rows = [];
        this.loading = false;
      },
    });
  }

  togglePayload(row: any): void {
    const id = row?.id;
    if (this.expandedId === id) {
      this.expandedId = null;
      return;
    }
    this.expandedId = id;
    try {
      this.payloadObj[id] = JSON.parse(row?.payload_json || '{}');
    } catch {
      this.payloadObj[id] = row?.payload_json;
    }
  }

  pretty(id: number): string {
    const v = this.payloadObj[id];
    try {
      return JSON.stringify(v, null, 2);
    } catch {
      return String(v);
    }
  }
}
