import { Component, Input, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  /** When true (embedded on Lab Master dashboard), hide redundant Close to same page. */
  @Input() embedded = false;

  updatelabs: any[] = [];
  selectresult: any = {};
  isView = false;
  loading = false;

  searchTerm = '';
  pageSize = 10;
  currentPage = 1;

  isAddBranchModal = false;
  isUpdateModal = false;
  addBranchDigitalAck = false;
  updateDigitalAck = false;

  /** Single branch row for “Add branch” from log view. */
  logBranchDraft: Record<string, string> = this.emptyLogBranch();

  /** Editable copy of lab for update modal. */
  editLab: any = {};

  signatureNow = '';

  constructor(private service: DataAccessService) {}

  emptyLogBranch(): Record<string, string> {
    return {
      branch_name: '',
      contact_person: '',
      contact_no: '',
      email: '',
      address: '',
      country: '',
      ocountry: '',
      permanent_state: '',
      city: '',
      pincode: '',
      gst_applicable: 'Not Applicable',
      tax: '',
    };
  }

  get empId(): string {
    return localStorage.getItem('emp_id') || localStorage.getItem('loger_id') || '';
  }

  get department(): string {
    return localStorage.getItem('department') || '';
  }

  get userDisplayName(): string {
    return localStorage.getItem('username') || this.empId;
  }

  gsts: any[] = [];

  ngOnInit(): void {
    this.getLabsLog();
    this.service.get('common.php?type=getGST').subscribe((response: any) => {
      this.gsts = Array.isArray(response) ? response : [];
    });
  }

  branchCount(row: any): number {
    return this.parseBranches(row).length;
  }

  get filteredLabs(): any[] {
    const list = Array.isArray(this.updatelabs) ? this.updatelabs : [];
    const q = (this.searchTerm || '').toLowerCase().trim();
    if (!q) {
      return list;
    }
    return list.filter((item) => {
      const blob = [
        item?.lab_name,
        item?.contact_no,
        item?.email,
        item?.contact_person,
        item?.address,
        item?.status,
        item?.city,
        item?.country,
        item?.lab_no,
      ]
        .map((x) => String(x || '').toLowerCase())
        .join(' ');
      return blob.includes(q);
    });
  }

  get totalFiltered(): number {
    return this.filteredLabs.length;
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.totalFiltered / this.pageSize) || 1);
  }

  get pagedLabs(): any[] {
    const f = this.filteredLabs;
    const start = (this.currentPage - 1) * this.pageSize;
    return f.slice(start, start + this.pageSize);
  }

  get lastIndexOnPage(): number {
    return Math.min(this.currentPage * this.pageSize, this.totalFiltered);
  }

  get branchRows(): any[] {
    return this.parseBranches(this.selectresult);
  }

  private parseBranches(row: any): any[] {
    const raw = row?.branch ?? row?.branches;
    if (raw == null || raw === '') {
      return [];
    }
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  onSearchChange(): void {
    this.currentPage = 1;
  }

  onPageSizeChange(): void {
    this.currentPage = 1;
  }

  goPrev(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  goNext(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  goFirst(): void {
    this.currentPage = 1;
  }

  goLast(): void {
    this.currentPage = this.totalPages;
  }

  goToPage(p: number): void {
    if (p >= 1 && p <= this.totalPages) {
      this.currentPage = p;
    }
  }

  /** Numeric page buttons (sliding window) for the lab log table. */
  get pageWindow(): number[] {
    const total = this.totalPages;
    const cur = this.currentPage;
    const width = 7;
    if (total <= width) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }
    let start = Math.max(1, cur - Math.floor(width / 2));
    let end = Math.min(total, start + width - 1);
    start = Math.max(1, end - width + 1);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
  }

  viewCertifidate(): void {
    const path = this.selectresult?.['certificate'];
    if (!path) {
      return;
    }
    const url = this.service.url + '../../upload/qc/' + encodeURIComponent(path);
    window.open(url, '_blank');
  }

  viewLic(): void {
    const path = this.selectresult?.['fda_lic_file'];
    if (!path) {
      return;
    }
    const url = this.service.url + '../../upload/qc/' + encodeURIComponent(path);
    window.open(url, '_blank');
  }

  getLabsLog(after?: () => void): void {
    this.loading = true;
    this.service.get('qc/lab.php?type=getLabsLog').subscribe({
      next: (response: any) => {
        this.updatelabs = Array.isArray(response) ? response : [];
        this.loading = false;
        this.currentPage = 1;
        this.syncSelectFromList();
        after?.();
      },
      error: () => {
        this.updatelabs = [];
        this.loading = false;
      },
    });
  }

  private syncSelectFromList(): void {
    const id = this.selectresult?.id;
    if (id == null || id === '') {
      return;
    }
    const found = this.updatelabs.find((x) => String(x.id) === String(id));
    if (found) {
      this.selectresult = { ...found };
    }
  }

  download(): void {
    this.service.open('qc/lab.php?type=downloadLabsLog');
  }

  view(row: any): void {
    this.selectresult = row && typeof row === 'object' ? { ...row } : {};
    this.isView = true;
  }

  closeView(): void {
    this.isView = false;
    this.isAddBranchModal = false;
    this.isUpdateModal = false;
  }

  openAddBranchModal(): void {
    this.signatureNow = new Date().toLocaleString();
    this.logBranchDraft = this.emptyLogBranch();
    this.addBranchDigitalAck = false;
    this.isAddBranchModal = true;
  }

  onLogBranchContact(event: Event): void {
    const el = event.target as HTMLInputElement;
    this.logBranchDraft.contact_no = (el.value || '').replace(/\D/g, '').slice(0, 15);
  }

  onLogBranchPin(event: Event): void {
    const el = event.target as HTMLInputElement;
    if (this.logBranchDraft.country === 'INDIA') {
      el.value = (el.value || '').replace(/\D/g, '').slice(0, 6);
      this.logBranchDraft.pincode = el.value;
    }
  }

  submitAddBranch(): void {
    if (!this.addBranchDigitalAck) {
      alertify.error('Confirm digital signature to add branch.');
      return;
    }
    const b = this.logBranchDraft;
    const emailRe = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    if (!b.branch_name || b.branch_name.trim().length < 2) {
      alertify.error('Enter branch name.');
      return;
    }
    if (!b.contact_person || b.contact_person.trim().length < 2) {
      alertify.error('Enter contact person.');
      return;
    }
    const phone = String(b.contact_no || '').replace(/\D/g, '');
    if (phone.length < 10) {
      alertify.error('Contact number at least 10 digits.');
      return;
    }
    if (!emailRe.test(String(b.email || '').trim())) {
      alertify.error('Invalid email.');
      return;
    }
    if (!b.address || b.address.trim().length < 3) {
      alertify.error('Enter address.');
      return;
    }
    if (!b.country) {
      alertify.error('Select country.');
      return;
    }
    if (!b.permanent_state || !b.city || !b.pincode) {
      alertify.error('Complete state, city and PIN/ZIP.');
      return;
    }
    if (b.gst_applicable === 'Applicable' && !b.tax) {
      alertify.error('Select tax %.');
      return;
    }
    const row = {
      branch_name: b.branch_name.trim(),
      contact_person: b.contact_person.trim(),
      contact_no: phone,
      email: b.email.trim(),
      address: b.address.trim(),
      country: b.country === 'INDIA' ? 'INDIA' : String(b.ocountry || b.country).trim(),
      permanent_state: b.permanent_state.trim(),
      city: b.city.trim(),
      pincode: b.pincode.trim(),
      gst_applicable: b.gst_applicable,
      tax: b.gst_applicable === 'Applicable' ? b.tax : '',
    };
    const fd = new FormData();
    fd.append('id', String(this.selectresult.id));
    fd.append('new_branches', JSON.stringify([row]));
    fd.append('digital_ack', '1');
    fd.append('sign_user_name', this.userDisplayName);
    fd.append('sign_department', this.department);
    this.service.post('qc/lab.php?type=appendLabBranches', fd).subscribe({
      next: (res: any) => {
        if (res?.status === 'success') {
          alertify.success('Branch added.');
          this.isAddBranchModal = false;
          this.getLabsLog();
        } else {
          alertify.error(res?.msg || res?.status || 'Save failed');
        }
      },
      error: () => alertify.error('Request failed'),
    });
  }

  openUpdateLaboratory(): void {
    this.signatureNow = new Date().toLocaleString();
    this.updateDigitalAck = false;
    const s = this.selectresult;
    const branches = this.branchRows;
    this.editLab = {
      id: s.id,
      lab_name: s.lab_name || '',
      fda_approved: s.fda_approved || 'No',
      contact_no: s.contact_no || '',
      contact_person: s.contact_person || '',
      email: s.email || '',
      address: s.address || '',
      country: s.country || '',
      ocountry: '',
      permanent_state: s.permanent_state || '',
      city: s.city || '',
      pincode: s.pincode || '',
      gst_applicable: s.tax ? 'Applicable' : 'Not Applicable',
      tax: s.tax || '',
      fda_lic_no: s.fda_lic_no || '',
      branch: JSON.parse(JSON.stringify(branches)),
    };
    if (this.editLab.country && this.editLab.country !== 'INDIA') {
      this.editLab.ocountry = this.editLab.country;
      this.editLab.country = 'Other';
    }
    this.isUpdateModal = true;
  }

  onEditContact(event: Event): void {
    const el = event.target as HTMLInputElement;
    this.editLab.contact_no = (el.value || '').replace(/\D/g, '').slice(0, 10);
  }

  onEditPin(event: Event): void {
    const el = event.target as HTMLInputElement;
    if (this.editLab.country === 'INDIA') {
      el.value = (el.value || '').replace(/\D/g, '').slice(0, 6);
      this.editLab.pincode = el.value;
    }
  }

  submitUpdateLaboratory(certInput: HTMLInputElement | undefined): void {
    if (!this.updateDigitalAck) {
      alertify.error('Confirm digital signature to update laboratory.');
      return;
    }
    const e = this.editLab;
    if (!e.lab_name || !e.contact_no || !e.email || !e.address || !e.contact_person) {
      alertify.error('Fill required lab fields.');
      return;
    }
    const countryResolved = e.country === 'INDIA' ? 'INDIA' : String(e.ocountry || e.country || '').trim();
    if (!countryResolved) {
      alertify.error('Select / enter country.');
      return;
    }
    const fd = new FormData();
    fd.append('id', String(e.id));
    fd.append('lab_name', e.lab_name);
    fd.append('fda_approved', e.fda_approved || 'No');
    fd.append('contact_no', String(e.contact_no).replace(/\D/g, '').slice(0, 15));
    fd.append('contact_person', e.contact_person);
    fd.append('email', e.email);
    fd.append('address', e.address);
    fd.append('country', e.country === 'INDIA' ? 'INDIA' : 'Other');
    fd.append('ocountry', e.country === 'INDIA' ? '' : countryResolved);
    fd.append('permanent_state', e.permanent_state || '');
    fd.append('city', e.city || '');
    fd.append('pincode', e.pincode || '');
    fd.append('gst_applicable', e.gst_applicable || 'Not Applicable');
    fd.append('tax', e.gst_applicable === 'Applicable' ? e.tax || '' : '');
    fd.append('fda_lic_no', e.fda_lic_no || '');
    fd.append('branch', JSON.stringify(Array.isArray(e.branch) ? e.branch : []));
    fd.append('digital_ack', '1');
    fd.append('sign_user_name', this.userDisplayName);
    fd.append('sign_department', this.department);
    if (certInput && certInput.files && certInput.files.length > 0) {
      fd.append('certificate', certInput.files[0], certInput.files[0].name);
    }
    this.service.post('qc/lab.php?type=updateLabMaster', fd).subscribe({
      next: (res: any) => {
        if (res?.status === 'success') {
          alertify.success('Laboratory updated.');
          this.isUpdateModal = false;
          if (certInput && certInput.value !== undefined) {
            certInput.value = '';
          }
          this.getLabsLog();
        } else {
          alertify.error(res?.msg || res?.status || 'Update failed');
        }
      },
      error: () => alertify.error('Request failed'),
    });
  }
}
