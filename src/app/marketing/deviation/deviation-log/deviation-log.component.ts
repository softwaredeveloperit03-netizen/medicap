import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-deviation-log',
  templateUrl: './deviation-log.component.html',
  providers:[DatePipe]
})
export class DeviationLogComponent implements OnInit {
  fromdate;
  todate;
  isView = false;
  selectedEntry;
  results;
  isApprover;
  comment;
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }
  ngOnInit() {
    this.getDeviations();

    if(localStorage.getItem('approver') === 'true' ) {
      this.isApprover = true;
    } else {
      this.isApprover =  false;
    }
  }

  getDeviations() {
    this.service.get('qms.php?type=getDeviationByDepartment&fromdate='+this.fromdate+'&todate='+this.todate).subscribe((response:any) => {
      this.results = response;
    });
  }

  updateDeviation() {
    this.service.get('qms.php?type=updateDeviation&id=' + this.selectedEntry.id + '&comment=' + this.comment).subscribe(response => {
      alert('Updated Successfully');
      this.isView = false;
      this.comment = '';
      this.getDeviations();
    });
  }

  getprint(){
    this.service.open('pdf1/deviation.php?type=deviationLog&fromdate='+this.fromdate+'&todate='+this.todate);
  }

  viewEntry(index) {
    this.selectedEntry = this.results[index];
    this.isView = true;
  }

  clearrecords(){}
}
