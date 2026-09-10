import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';



@Component({
  selector: 'app-ar',
  templateUrl: './ar.component.html',
  styleUrls: ['./ar.component.css'],
  providers: [DatePipe]
})
export class ArComponent implements OnInit {

  isView = false;
  samplings;
  result =[
    {'status':'active' },
    {'status':'pending'},
    {'status':'checked'}
  ];
  from_date = '';
  to_date = '';
  selectedCountry = '';
  

  selectedReport = [];
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getARReport();
  }

  getARReport() {
    let temp=this.selectedCountry['status'];
    let temp1=this.selectedCountry['ar_no'];
    let temp2=this.selectedCountry['material_code'];
    let temp3=this.selectedCountry['grn_no'];
    // this.service.get('qc/testing/raw.php?type=getPendingTestingReport&from_date=' + this.from_date + '&to_date=' + this.to_date + '&status='+temp +'&ar_no='+temp1 + '&material_code='+temp2+'&grn_no='+temp3).subscribe(response => {
      this.service.get('qc/testing/raw.php?type=getCheckingAR&from_date=' + this.from_date + '&to_date=' + this.to_date + '&status='+temp +'&ar_no='+temp1 + '&material_code='+temp2+'&grn_no='+temp3).subscribe(response => {
 
    this.samplings = response;
    });
  }

  viewReport(index) {
    this.selectedReport = this.samplings[index];
    this.isView = true;
  }
  downloadReport() {
    
    // this.service.open('pdf1/testing.php?type=ARReportlog');
    this.service.open('qc/testing/raw.php?type=downloadTestingLog&from_date=' + this.from_date + '&to_date=' + this.to_date);

  }
  downloadPDF(ar_no, type) {
    // if(type == 'manual'){
    //   this.service.open('pdf1/testing.php?type=ARReport&ar_no='+ar_no);
    // }else{
    //   this.service.open('pdf1/testing.php?type=ARReportdigital&ar_no='+ar_no);
    // }

    if (type == 'manual') {
      this.service.open('qc/testing/raw.php?type=downloadTestingReport&id=' + this.selectedReport['id']);
    } else {
      this.service.open('qc/testing/raw.php?type=downloadTestingReportDigital&id=' + this.selectedReport['id']);
    }
  }

}
