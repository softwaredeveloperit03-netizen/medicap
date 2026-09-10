import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-maintenance',
  templateUrl: './maintenance.component.html',
  styleUrls: ['./maintenance.component.css'],
  providers:[DatePipe]
})
export class MaintenanceComponent implements OnInit {

  isView = false;
  notes;
  selectedNote;
  isApprover;
  from_date = '';
  to_date = '';

  constructor(private service: DataAccessService,private datePipe:DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getMaintenanceHistory();

    if(localStorage.getItem('approver')== 'true') {
      this.isApprover = true;
    } else {
      this.isApprover = false;
    }
  }

  getMaintenanceHistory() {
    this.service.get('engineering/maintenance.php?type=getMaintenanceHistory&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.notes = response;
    });
  }

  showNote(index) {
    this.selectedNote = this.notes[index];
    this.isView = true;
  }

  download(){
    this.service.open('engineering/maintenance.php?type=downloadMaintenanceHistory&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }


}
