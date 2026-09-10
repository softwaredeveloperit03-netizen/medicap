import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  isView = false;
  results;

  selectedMOA = [];
  materiallist = [];

  material_code = '';
  fromdate = '';
  todate = '';
  isShow=false;
  selectedMethod=[];
  constructor(private service: DataAccessService,private datePipe: DatePipe,) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getMaterialList();
    this.getReports();
  }
  getMaterialList(){
    this.service.get('qc/method.php?type=getRawMOAMaterial').subscribe((response:any)=>{
      this.materiallist = response;
    })
  }
  
  getReports() {
    this.service.get('qc/method.php?type=getRawMOALog&material_code='+this.material_code+'&fromdate='+this.fromdate+'&todate='+this.todate).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedMOA = this.results[index];
    this.isView = true;
  }

  show(index){
    this.selectedMethod = this.selectedMOA['tests'];
    // this.selectedMethod = test[index];
    console.log('test', this.selectedMethod);
    this.isShow=true;
  }

  clearrecords(){
    this.material_code = '';
    this.fromdate = '';
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.getReports();
  }

  download(sign, value){
    if(sign == 'manual'){
      this.service.open('qc/method.php?type=downloadRawMOARecord&id='+value)
    }else{
      this.service.open('qc/method.php?type=downloadRawMOARecordDigital&id='+value)
    }
    
  }

  downloadReport(){
    this.service.open('qc/method.php?type=ddownloadRawMOALog&material_code='+this.material_code+'&fromdate='+this.fromdate+'&todate='+this.todate);
  }

}
