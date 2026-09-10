import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common'; 
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  providers: [DatePipe],
})
export class ApprovalComponent implements OnInit {

  
  isView = false;
  results: any[] = [];
  pageSize = 10;
  materialTypeFilter = 'All';
  searchQuery = '';

  selectedResult: any = {};
  remark = '';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {}

  ngOnInit(): void {
    this.getChallansLog();
  }
 

  getChallansLog() {
    this.service.get('store/challan.php?type=getChallansLog').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }


 

  view(data) {
    this.selectedResult = data;
    this.isView = true;
    this.getUploadChallans();
  }


  
  uploadedFileNames;
  getUploadChallans() {
    this.service.get('store/challan.php?type=getUploadedChallans&ch_no=' +this.selectedResult['ch_no'] +'&po_no=' +this.selectedResult['po_no']  +'&vendor_no=' +this.selectedResult['vendor_no']).subscribe((response) => {
        this.uploadedFileNames = response;
    });
  }

 
 
  downloadDocumentChecklist() {
    this.service.open('store/raw.php?type=downloadDocumentChecklist&challan_no=' +encodeURIComponent(this.selectedResult['challan_no'])) ;
  }


    
  viewFile(url1) {
    let url = this.service.url + '../../upload/challan/' + url1 +'?v=1';
    window.open(url, '_blank');
  }

 
  get filteredMaterials(): any[] {
    const list = Array.isArray(this.results) ? this.results : [];

    const byMaterialType = list.filter((material: any) => {
      if (!this.materialTypeFilter || this.materialTypeFilter === 'All') {
        return true;
      }
      return String(material?.material_type || '').toLowerCase() === this.materialTypeFilter.toLowerCase();
    });

    const query = (this.searchQuery || '').toLowerCase().trim();
    if (!query) {
      return byMaterialType;
    }

    return byMaterialType.filter((material: any) => {
      return Object.entries(material || {}).some(([key, value]) => {
        if (key === 'entry_date' || key === 'po_date' || key === 'challan_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && !isNaN(dateValue.getTime()) && dateValue.toISOString().slice(0, 10).includes(query);
        }
        return value !== null && value !== undefined && value.toString().toLowerCase().includes(query);
      });
    });
  }

  clearFilters(): void {
    this.materialTypeFilter = 'All';
    this.searchQuery = '';
  }

 
}
