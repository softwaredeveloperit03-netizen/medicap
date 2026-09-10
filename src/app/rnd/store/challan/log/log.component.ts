import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {
  isView = false;
  results;
  vendors;
  selectedResult = [];
  vendor_no='';
  from_date='';
  to_date='';
  status='approve';
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getChallansLog();
    this.getVendors();
  }

  getChallansLog(){
    this.service.get('store/challan.php?type=getChallansLog&vendor_no='+this.vendor_no  +'&to_date='+this.to_date +'&from_date='+this.from_date).subscribe(response => {
      this.results = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  downloadLog(){
    this.service.open('pdf1/store.php?type=challanLog')
  }
  download(){
    this.service.open('store/challan.php?type=downloadChallanLog&vendor_no='+this.vendor_no  +'&to_date='+this.to_date +'&from_date='+this.from_date +'&status='+status)
  }

  

}
