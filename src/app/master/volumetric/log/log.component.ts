import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { VolumetricLogModalService } from '../volumetric-log-modal.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit, OnChanges {
  /** When true (embedded on Volumetric Master dashboard), hide outer card chrome. */
  @Input() embedded = false;
  @Input() externalSearch = '';
  @Output() recordCountChange = new EventEmitter<number>();

  results: any[] = [];
  searchQuery = '';
  statusFilter = 'All';
  pageSize = 10;
  currentPage = 1;
  loading = false;

  constructor(
    private service: DataAccessService,
    private modals: VolumetricLogModalService
  ) {}

  ngOnInit() {
    this.modals.reloadList = () => this.getVolumetricMaster();
    this.getVolumetricMaster();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['externalSearch'] && this.embedded) {
      this.searchQuery = this.externalSearch || '';
      this.onFilterChange();
      this.emitCount();
    }
  }

  getVolumetricMaster() {
    this.loading = true;
    this.service.get('qc/volumetric.php?type=getVolumetricMaster&include_deleted=1').subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
      this.emitCount();
    }, () => {
      this.results = [];
      this.loading = false;
      this.emitCount();
    });
  }

  private emitCount(): void {
    this.recordCountChange.emit(this.filteredResults.length);
  }

  download() {
    this.service.open('qc/volumetric.php?type=downloadVolumetricMaster');
  }

  get normalizedResults(): any[] {
    return (this.results || []).map((r: any) => {
      const status = (r?.status || '').toString().toLowerCase().trim();
      let statusText = 'Approved';
      if (Number(r?.is_deleted) === 1 || status === 'deleted') {
        statusText = 'Deleted';
      } else if (status === 'pending') {
        statusText = 'Pending';
      } else if (status === 'rejected' || status === 'reject') {
        statusText = 'Rejected';
      } else if (status === 'approved' || status === 'approve') {
        statusText = 'Approved';
      } else if (status) {
        statusText = r.status;
      }
      return {
        ...r,
        statusText,
      };
    });
  }

  get filteredResults(): any[] {
    const q = (this.searchQuery || '').toLowerCase().trim();
    return this.normalizedResults.filter((r: any) => {
      const matchesQuery =
        !q ||
        (r.solution_no || '').toLowerCase().includes(q) ||
        (r.solution_name || '').toLowerCase().includes(q) ||
        (r.standard_type || '').toLowerCase().includes(q) ||
        (r.statusText || '').toLowerCase().includes(q);
      const matchesStatus =
        this.statusFilter === 'All' ||
        (r.statusText || '').toLowerCase() === this.statusFilter.toLowerCase();
      return matchesQuery && matchesStatus;
    });
  }

  get totalPages(): number {
    const total = this.filteredResults.length;
    return Math.max(1, Math.ceil(total / this.pageSize));
  }

  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, i) => i + 1);
  }

  get pagedResults(): any[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredResults.slice(start, start + this.pageSize);
  }

  onFilterChange() {
    this.currentPage = 1;
    this.emitCount();
  }

  onPageSizeChange(size: any) {
    this.pageSize = Number(size) || 10;
    this.currentPage = 1;
  }

  onPageNoChange(page: any) {
    const p = Number(page) || 1;
    this.currentPage = Math.min(Math.max(1, p), this.totalPages);
  }

  getStatusClass(status: string): string {
    const s = (status || '').toLowerCase();
    if (s === 'approved') return 'label label-success';
    if (s === 'pending') return 'label label-info';
    if (s === 'rejected') return 'label label-danger';
    if (s === 'deleted') return 'label label-default';
    return 'label';
  }

  openEdit(row: any) {
    this.modals.openEdit(row);
  }

  openHistory(row: any) {
    this.modals.openHistory(row);
  }
}
