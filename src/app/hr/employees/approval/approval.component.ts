import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  employees: any[] = [];
  selectedResult: any = {};
  loading = false;
  docsLoading = false;
  searchQuery = '';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingEmployees();
  }

  getPendingEmployees() {
    this.loading = true;
    this.service.getJsonArray('hr/employee.php?type=getPendingEmployees').subscribe((response: any[]) => {
      this.employees = Array.isArray(response) ? response.map((row) => this.withNormalizedDocs(row)) : [];
      this.loading = false;
    }, () => {
      this.employees = [];
      this.loading = false;
    });
  }

  view(data) {
    this.selectedResult = this.withNormalizedDocs(data || {});
    this.isView = true;
    this.loadDocuments(this.selectedResult['emp_id']);
  }

  private withNormalizedDocs(row: any): any {
    const copy = { ...(row || {}) };
    copy.documents = this.normalizeDocs(copy.documents);
    return copy;
  }

  private normalizeDocs(docs: any): any[] {
    if (!Array.isArray(docs)) {
      return [];
    }
    return docs.map((d) => ({
      ...d,
      documentName: d.documentName || d.document_name || d.DocumentName || '',
      fileName: d.fileName || d.file_name || d.filename || d.FileName || d.doc || '',
    }));
  }

  private loadDocuments(empId: string) {
    if (!empId) {
      return;
    }
    this.docsLoading = true;
    this.service.getJsonArray('hr/employee.php?type=getUploadedDocByEmpId&emp_idForDoc=' + encodeURIComponent(empId))
      .subscribe((response: any[]) => {
        this.selectedResult = {
          ...this.selectedResult,
          documents: this.normalizeDocs(response),
        };
        this.docsLoading = false;
      }, () => {
        this.docsLoading = false;
      });
  }

  isCanadaCountry(country: string | null | undefined): boolean {
    return String(country || '').trim() === 'Canada';
  }

  isIndiaCountry(country: string | null | undefined): boolean {
    return String(country || '').trim() === 'India';
  }

  getSin(emp: any): string {
    if (!emp || !this.isCanadaCountry(emp.permanent_country)) {
      return '';
    }
    return emp.pan || '';
  }

  getPan(emp: any): string {
    if (!emp || !this.isIndiaCountry(emp.permanent_country)) {
      return '';
    }
    return emp.pan || '';
  }

  getEmergencyContact(emp: any): string {
    return emp?.branch_name || '';
  }

  getTransitNo(emp: any): string {
    const routing = String(emp?.ifsc_neft || '').trim();
    if (!routing) return '';
    const parts = routing.split('-');
    return parts[0] || routing;
  }

  getInstitutionNo(emp: any): string {
    const routing = String(emp?.ifsc_neft || '').trim();
    if (!routing) return '';
    const parts = routing.split('-');
    return parts.length > 1 ? parts.slice(1).join('-') : '';
  }

  displayValue(value: unknown): string {
    if (value == null || value === '') {
      return 'NA';
    }
    const text = String(value).trim();
    return text === '' ? 'NA' : text;
  }

  formatDate(value: unknown): string {
    if (value == null || value === '') {
      return 'NA';
    }
    const d = new Date(value as string);
    if (isNaN(d.getTime())) {
      return 'NA';
    }
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
  }

  viewDoc(url) {
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }

  updateEmployee(status) {
    this.service.get('hr/employee.php?type=updateEmployee&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('Employee Updated Successfully');
        this.isView = false;
        this.getPendingEmployees();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



  get filteredMaterials(): any[] {
    const list = Array.isArray(this.employees) ? this.employees : [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return list;
    }
    
    const query = this.searchQuery.toLowerCase().trim();
  
    return list.filter(material => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && !isNaN(dateValue.getTime()) && dateValue.toISOString().slice(0, 10).includes(query);
        }
        return value && value.toString().toLowerCase().includes(query);
      });
    });
  }


  



}
