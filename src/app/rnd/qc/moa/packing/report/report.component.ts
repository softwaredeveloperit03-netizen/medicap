import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css']
})
export class ReportComponent implements OnInit {

  isView = false;
  results;

  selectedMOA = [];
  materiallist = [];
  resultslength = 0;
  material_code = '';
  fromdate = '';
  todate = '';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getReports();
    this.getMaterialList();
  }
  getMaterialList(){
    this.service.get('qc/method.php?type=getPackingMOAMaterial').subscribe((response:any)=>{
      this.materiallist = response;
    })
  }

  getReports() {
    this.service.get('qc/method.php?type=getPackingMOALog&material_code='+this.material_code+'&fromdate='+this.fromdate+'&todate='+this.todate).subscribe(response => {
      this.results = response;
      this.resultslength = this.results.length;
    });
  }

  view(index) {
    this.selectedMOA = this.results[index];
    this.isView = true;
  }

  clearrecords(){
    this.material_code = '';
    this.fromdate = '';
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.getReports();
  }

  download(sign, value){
    if(sign == 'manual'){
      this.service.open('pdf1/moa.php?type=PackingMOA&id='+value)
    }else{
      this.service.open('pdf1/moa.php?type=PackingMOAdigital&id='+value)
    }
  }

  downloadReport(){
    this.service.open('pdf1/moa.php?type=PackingMOALog&material_code='+this.material_code+'&fromdate='+this.fromdate+'&todate='+this.todate);
  }
}
