import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
  vendor_no = '';
  from_date = '';
  to_date = '';

  selectedReport = [];
  constructor(private service:DataAccessService,private datePipe:DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getRetestsLog();
    this.getVendors();
  }

  getRetestsLog(){
    this.service.get('store/packing.php?type=getRetestsLog&vendor_no=' + this.vendor_no + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  view(index){
    this.selectedReport = this.results[index];
    this.isView = true;
  }

}
