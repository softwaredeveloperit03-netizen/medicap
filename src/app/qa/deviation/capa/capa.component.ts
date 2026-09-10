import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-capa',
  templateUrl: './capa.component.html',
  styleUrls: ['./capa.component.css'],
  providers:[DatePipe]
})
export class CapaComponent implements OnInit {

  isView = false;
  results;
  fromdate;
  todate;

  selectedDev = [];
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getCapaDeviations();
  }

  getCapaDeviations() {
    this.service.get('deviation.php?type=getCapaDeviations&fromdate='+this.fromdate+'&todate='+this.todate).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }

}
