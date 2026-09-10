import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results
  constructor(private service : DataAccessService) { }

  ngOnInit() {
    this.getSampleQtyLog();
  }
  getSampleQtyLog()
  {
    this.service.get('qc/sampling/samplingqty.php?type=getSampleQtyLog').subscribe(response=>{
      this.results = response;
    })
  }
  download(){
    this.service.open('qc/sampling/samplingqty.php?type=downloadSampleQtyLog')  
  }
}
