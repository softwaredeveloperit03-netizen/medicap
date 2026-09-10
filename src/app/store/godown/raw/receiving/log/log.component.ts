import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]

})
export class LogComponent implements OnInit {
  challan_for='';
  from_date = '';
  today='';
  to_date = '';
  isView = false;
  results;
  vendor_no='';
  material_type='';
  material_name='';
  receiving_date='';
  status='';
  selectedReport = [];
  vendors;
  damage;
  material_subtype='';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit() {
    this.getReceivingLog();
    this.getVendors();
  }

  getReceivingLog() {
    this.service.get('store/raw.php?type=getReceivingLog&material_subtype='+this.material_subtype+'&from_date='+this.from_date + '&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.damage = this.selectedReport['receiving_details'];
    console.log(this.damage)
    this.isView = true;
  }

  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  downloadPDF(sign){
    this.service.open('store/raw.php?type=receivingMaterialPDF&pdfsign='+sign+'&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=receivingMaterialLogPDF&material_type='+this.material_type+'&from_date='+this.from_date+'&to_date='+this.to_date+'&challan_for='+this.challan_for)
  }

  AllRecord(){
    this.service.get('store/raw.php?type=getAllReceivingLog').subscribe((response : any) => {
      this.results = response;
    });
    this.material_subtype='';
  }
}
