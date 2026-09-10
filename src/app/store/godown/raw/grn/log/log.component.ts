import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {
  from_date = '';
  to_date = '';
  isView = false;
  material_type='';
  results;
  grndetails=[];
  selectedReport = [];
  challan_for='';
  today='';
  material_subtype='';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd'); 
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd'); }

  ngOnInit() {
    this.getGRNLog();
  }

  getGRNLog() {
    this.service.get('store/raw.php?type=getGRNLog&from_date=' + this.from_date + '&to_date=' + this.to_date+'&material_subtype='+this.material_subtype).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.grndetails=this.selectedReport['grn_details'];
    this.isView = true;
  }

  download() {
    this.service.open('store/raw.php?type=GRNPDF&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=GRNLogPDF&from_date=' + this.from_date + '&to_date=' + this.to_date+'&challan_for='+this.challan_for);
  }


  
  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    url = this.service.url + 'upload/challan/' + url;
    window.open(url, '_blank');
  }

  AllRecord(){
    this.service.get('store/raw.php?type=getAllGRNLog').subscribe((response : any) => {
      this.results = response;
    });
    this.material_subtype='';
  }
}
