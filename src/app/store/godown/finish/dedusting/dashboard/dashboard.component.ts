import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  isView = false;
  results;
  vendors;
  selectedReport = [];
  material_type='';
  status='';
  vendor_no='';
  from_date = '';
  today='';
  to_date='';
  results1;
  material_name='';
  grn_no='';

  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit() {
    this.getDedustingMaterials();
    this.getVendors();
  }

  getDedustingMaterials() {
    this.service.get('store/raw.php?type=getDedustingMaterials&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results1 = response;
       this.filterStock();
    });
  }
  filterStock() {
    this.results = [];
    console.log(this.results1)
    for (let i = 0; i < this.results1.length; i++) {
      let material = this.results1[i];/* material['grn_no'].toUpperCase().includes(this.grn_no.toUpperCase()) && */
      if ( material['material_subtype'].toUpperCase().includes(this.material_type.toUpperCase()) && material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())) {
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
  downloadPDF(sign){
    this.service.open('store/raw.php?type=dedustingMaterialPDF&pdfsign='+sign+'&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=downloadDedustingMaterials&from_date='+this.from_date+'&to_date='+this.to_date)
  }

}
