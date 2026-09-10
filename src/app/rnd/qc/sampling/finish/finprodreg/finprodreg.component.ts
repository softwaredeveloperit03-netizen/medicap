import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-finprodreg',
  templateUrl: './finprodreg.component.html',
  styleUrls: ['./finprodreg.component.css'],
})
export class FinprodregComponent implements OnInit {

  constructor(private service:DataAccessService  , private router: Router) {

  }

result;
  ngOnInit(): void {
     this.getSampligLog();
  }
    getSampligLog(){
    this.service.get('qc/sampling/raw.php?type=getSamplingFPReciptLog').subscribe(response =>{
      this.result =response;
    });
  }

    getData(){
    this.service.get('qc/sampling/raw.php?type=getSamplingFPReciptLogDAte&fromDate='+this.fromDAte +'&ToDate='+this.ToDAte).subscribe(response =>{
      this.result =response;
    });
  }

    downloadPDF() {
    this.service.open(
      'qc/sampling/raw.php?type=getSamplingFPReciptLogDAtePDF&fromDate='+this.fromDAte +'&ToDate='+this.ToDAte);
  }

fromDAte;
ToDAte;


}
