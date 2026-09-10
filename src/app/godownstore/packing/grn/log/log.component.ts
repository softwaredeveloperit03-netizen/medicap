import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from "@angular/common";
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  vendors;
  selectedReport = [];
  material_subtype='';
  vendor_no='';
  status='';
  material_type='';
  from_date='';
  to_date='';
  today='';
  vendor_name='';

  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }


  ngOnInit(): void {
    this.getGRNLog();
    this.getVendors();
  }

  getGRNLog() {
    this.service.get('store/packing.php?type=getGRNLog&vendor_name='+this.vendor_name+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  download()
  {
    this.service.open('store/packing.php?type=downloadGRNLog&vendor_no='+this.vendor_no+'&from_date='+this.from_date+'&to_date='+this.to_date);
  }
  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  //  download1() {
  //   let url = this.service.url + "pdf1/grn-preparation.php?id=" + this.selectedReport['id'] + '&token=' + localStorage.getItem('token');
  //   window.open(url, '_blank');
  // }

  download1()
  {
    this.service.open('store/packing.php?type=downloadGRN&chId='+this.selectedReport['id']+'&from_date='+this.from_date+'&to_date='+this.to_date);
  }

  AllRecord(){
    this.service.get('store/packing.php?type=getAllGRNLog').subscribe((response : any) => {
      this.results = response;
    });
    this.vendor_name='';
  }

}
