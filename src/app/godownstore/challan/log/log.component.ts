import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
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
  material_type='';
  from_date='';
  to_date='';
  today='';
  vendor_no='';
  status='approve';
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getChallansLog();
    this.getVendors();
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

  getChallansLog(){
    this.service.get('store/challan.php?type=getChallansLog&material_type='+this.material_type  +'&to_date='+this.to_date +'&from_date='+this.from_date).subscribe(response => {
      this.results = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  
 
  view(index,pageNo) {
    // let indexData = 10+()
    // let recordIndex = (10*(1-pageNo)+index);
    // this.selectedResult = this.results[recordIndex];
    this.selectedResult = this.results[index];
    this.isView = true;
    console.log('this.selectedResult :>> ', this.selectedResult); 
  }


  downloadLog(){
    this.service.open('pdf1/store.php?type=challanLog')
  }
  download(){
    this.service.open('store/challan.php?type=downloadChallanLog1&vendor_no='+this.vendor_no  +'&to_date='+this.to_date +'&from_date='+this.from_date +'&status='+status)
  }
  // download(){
  //   this.service.open('store/challan.php?type=downloadChallanLog1&vendor_no='+this.vendor_no  +'&to_date='+this.to_date +'&from_date='+this.from_date +'&status='+status)
  // }

  upload(url){
    url = this.service.url + '../../upload/challan/' + url;
    window.open(url, '_blank');
  // window.open(this.service.url+this.selectedResult['challan_file']);
    // window.open(this.service.url + this.selectedResult['challan_file']);
    // url = this.service.url + 'upload/challan/' + url;
    // window.open(url, '_blank');
  }

  AllRecord(){
    this.service.get('store/challan.php?type=getAllChallansLog').subscribe((response : any) => {
      this.results = response;
    });
    this.material_type='';
    this.from_date='';
    this.to_date='';
  }

}
