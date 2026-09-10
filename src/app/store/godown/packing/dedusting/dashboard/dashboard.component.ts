import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  providers:[DatePipe]

})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'New', route: 'new', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'approval', title: 'Approval', route: 'approval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

  material_subtype='';
  from_date = '';
  today='';
  to_date='';
    isView = false;
    results;
    vendors;
    selectedReport = [];
    material_type='';
    vendor_no='';
    status='';
    results1;
    material_name=''; 
    
    constructor(private service: DataAccessService,private datePipe: DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      }
  
  
    ngOnInit(): void {
      this.getDedustingsLog();
      this.getVendors();
    }
  
    getDedustingsLog() {
      this.service.get('store/packing.php?type=getDedustingsLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
        this.results1 = response;
      this.filterStock();

      });
    }
    filterStock() {
      this.results = [];
      for (let i = 0; i < this.results1.length; i++) {
        let material = this.results1[i];/* material['grn_no'].toUpperCase().includes(this.grn_no.toUpperCase()) && */
        if ( material['material_subtype'].toUpperCase().includes(this.material_type.toUpperCase()) ) {
          this.results[this.results.length] = material;
        }
      }
    }
    getVendors() {
      this.service.get('common.php?type=getVendors').subscribe(response => {
        this.vendors = response;
      });
    }
  
  
    view(index) {
      this.selectedReport = this.results[index];
      this.isView = true;
    }

    downloadLog(){
      this.service.open('store/packing.php?type=downloaddedustingofmaterial&vendor_no=' + this.vendor_no + '&from_date=' + this.from_date + '&to_date=' +this.to_date)
    }

    AllRecord(){
      this.results =this.results1;
      this.material_subtype='';
      
  }
  }
  