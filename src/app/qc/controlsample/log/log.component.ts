import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  entries;

  selectedResult = [];

  material_type = 'Raw Material';
  from_date = '';
  to_date = '';
  constructor(private datePipe: DatePipe,private service: DataAccessService, private router: Router) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getControlsamples();
  }

  getControlsamples() {
    const url =
      'qa/controlsample.php?type=getControlSamples' +
      '&material_type=' + encodeURIComponent(this.material_type || '') +
      '&from_date=' + encodeURIComponent(this.from_date || '') +
      '&to_date=' + encodeURIComponent(this.to_date || '');
    this.service.get(url).subscribe({
      next: (response: any) => {
        this.entries = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.entries = [];
      }
    });
  }

  view(index){
    this.selectedResult = this.entries[index];
    this.isView = true;
  }

  close() {
    this.router.navigate(['/qc/controlsample']);
  }

  getprint(){
    this.service.open(
      'pdf1/controlsample.php?type=controlsamplelog' +
      '&material_type=' + encodeURIComponent(this.material_type || '') +
      '&fromdate=' + encodeURIComponent(this.from_date || '') +
      '&todate=' + encodeURIComponent(this.to_date || '')
    );
  }

}
