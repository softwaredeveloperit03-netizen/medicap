import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { STANDARD_GLASSWARE_TEMPLATES } from '../shared/standard-glasswares.constants';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
    reports;
    selectedReview = [];
    isView = false;
    item=[];
    name='';
    seedingStandards = false;
    loading = false;
    constructor(private service: DataAccessService) { }
  
    ngOnInit(): void {
     this.getGlasswaresLog();
    }

    seedStandardGlasswares(): void {
      if (this.seedingStandards) {
        return;
      }
      const masterUserName =
        localStorage.getItem('username') ||
        localStorage.getItem('user') ||
        localStorage.getItem('firstname') ||
        'Master User';
      this.seedingStandards = true;
      const payload = {
        masterUserName,
        glasswares: STANDARD_GLASSWARE_TEMPLATES,
      };
      this.service.post('qc/glassware.php?type=seedStandardGlasswares', JSON.stringify(payload)).subscribe({
        next: (response: any) => {
          this.seedingStandards = false;
          if (response?.status === 'success') {
            const added = response.added ?? 0;
            const skipped = response.skipped ?? 0;
            alertify.success(`Lab glassware seeded (${added} added, ${skipped} already exist).`);
            this.getGlasswaresLog();
          } else {
            alertify.error(response?.message || 'Could not seed glassware.');
          }
        },
        error: () => {
          this.seedingStandards = false;
          alertify.error('Could not seed glassware. Check your connection / database.');
        },
      });
    }
    //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  
    getGlasswaresLog() {
      this.service.get('qc/glassware.php?type=getGlasswaresLog').subscribe(response => {
        this.reports = response;
        this.filterItem();
      });
    }
  
    download() {
      this.service.open('qc/glassware.php?type=downloadGlasswaresLog&name=' +this.name)
    }
    // view(index) {
    //   this.selectedReview = this.reports[index];
    //   this.isView = true;
    // }
    view(index) {
      this.selectedReview = this.item[index];
      this.isView = true;
    }
    openlic(file) {
      if (file !== '') {
        window.open(this.service.url + '../../upload/product/' + file);
      } else {
        alertify.error('File not available');
      }
    }
    
    filterItem() {
      this.item = [];
      const list = Array.isArray(this.reports) ? this.reports : [];
      for (let i = 0; i < list.length; i++) {
        const material = list[i];
        const n = (material['name'] || '').toString().toUpperCase();
        if (n.includes((this.name || '').toUpperCase())) {
          this.item[this.item.length] = material;
        }
      }
    }

    AllRecord(){
      this.item =this.reports;
      this.name='';
     
  }


  }
  