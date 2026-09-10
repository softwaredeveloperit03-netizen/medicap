import { Component, OnInit } from '@angular/core';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sampling-room-new-log',
  templateUrl: './new-log.component.html',
  styleUrls: ['./new-log.component.css']
})
export class NewLogComponent implements OnInit {
  material_type = '';
  entries: any[] = [];
  loading = false;

  constructor(public service: DataAccessService) {}

  ngOnInit() {
    this.loadLiveLog();
  }

  loadLiveLog() {
    this.loading = true;
    let url = 'qc/sampling/sampling_room.php?type=getSamplingRoomLiveLog';
    if (this.material_type) {
      url += '&material_type=' + encodeURIComponent(this.material_type);
    }
    this.service.get(url).subscribe((res: any) => {
      const rows = this.normalizeLiveLog(res).map((row) => this.mapSamplingRow(row));
      if (rows.length) {
        this.entries = rows;
        this.loading = false;
        return;
      }
      this.loadLiveLogFallback();
    }, () => this.loadLiveLogFallback());
  }

  private loadLiveLogFallback() {
    const types = this.material_type
      ? [this.material_type]
      : ['Raw Material', 'Packing Material'];
    const calls = [];
    types.forEach((t) => {
      const typeQ = encodeURIComponent(t);
      calls.push(
        this.service.get('qc/sampling/raw.php?type=getPendingSamplingDataForBalanceCleaning&material_type=' + typeQ).pipe(
          catchError(() => of([]))
        )
      );
      calls.push(
        this.service.get('qc/sampling/raw.php?type=getPendingSamplingForm&material_type=' + typeQ).pipe(
          catchError(() => of([]))
        )
      );
    });
    forkJoin(calls).subscribe((results: any[]) => {
      const mapped = [];
      const seen = {};
      (results || []).forEach((list) => {
        (Array.isArray(list) ? list : []).forEach((row) => {
          const id = row?.id || row?.sampling_id;
          if (id && seen[id]) {
            return;
          }
          if (id) {
            seen[id] = true;
          }
          mapped.push(this.mapSamplingRow(row));
        });
      });
      this.entries = mapped;
      this.loading = false;
    }, () => {
      this.entries = [];
      this.loading = false;
    });
  }

  private normalizeLiveLog(res: any): any[] {
    if (Array.isArray(res)) {
      return res;
    }
    if (res && Array.isArray(res.entries)) {
      return res.entries;
    }
    if (res && Array.isArray(res.data)) {
      return res.data;
    }
    return [];
  }

  private parseAgents(raw: any): any[] {
    if (!raw) {
      return [];
    }
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    }
    return [];
  }

  private mapSamplingRow(row: any): any {
    const agents = Array.isArray(row?.cleaning_agents)
      ? row.cleaning_agents
      : this.parseAgents(row?.cleaningAgentsUsed);
    const status = (row?.status || '').toString();
    const balanceDate = (row?.weighBalCleanDate || '').toString();
    const balanceDone =
      status.toLowerCase().indexOf('balance') >= 0 ||
      (balanceDate !== '' && balanceDate !== '0000-00-00');
    const cleanType = (row?.cleaningType || '').toString().toLowerCase();
    const isPartial = cleanType === 'partial';
    return {
      ...row,
      sampling_id: row?.sampling_id || row?.id,
      material_description: row?.material_description || row?.material_name || row?.material_code || '-',
      material_lot_no: row?.material_lot_no || row?.batch_no || '',
      cleaning_full: row?.cleaning_full || (isPartial ? 'No' : 'Yes'),
      cleaning_partial: row?.cleaning_partial || (isPartial ? 'Yes' : 'No'),
      cleaning_agents: agents,
      cleaning_agent_name: row?.cleaning_agent_name,
      balance_cleaned: row?.balance_cleaned || (balanceDone ? 'Yes' : 'No'),
      balance_verified: row?.balance_verified || (balanceDone ? 'Yes' : 'No'),
      magnehelic_reading_1: row?.magnehelic_reading_1 || row?.weighBalMagnehelic1,
      magnehelic_reading_2: row?.magnehelic_reading_2 || row?.weighBalMagnehelic2,
      magnehelic_reading_3: row?.magnehelic_reading_3 || row?.weighBalMagnehelic3,
      performed_by: row?.performed_by || row?.cleaningDoneBy,
      verified_by: row?.verified_by || row?.weighBalCleanDoneBy,
    };
  }

  checkMark(val: string): string {
    return (val === 'Yes' || val === 'yes') ? '✓' : '';
  }

  agentDisplay(row: any): string {
    if (row.cleaning_agents && row.cleaning_agents.length) {
      return row.cleaning_agents
        .map((a: any) => a.material_name || a.material_code)
        .filter(Boolean)
        .join(', ');
    }
    return row.cleaning_agent_name || '-';
  }
}
