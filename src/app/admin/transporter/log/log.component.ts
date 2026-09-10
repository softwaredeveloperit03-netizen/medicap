import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-transporter-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  isView = false;
  results: any[] = [];
  selectedResult: any = {};
  isuser = 'No';
  isapprover = 'No';
  rights: any;
  loggedInDept: string | null = null;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getTransportersLog();
    this.get_rights();
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        if (Array.isArray(response) && response[0]) {
          this.isuser = response[0].isuser || 'No';
          this.isapprover = response[0].isapprover || 'No';
        }
      });
  }

  getTransportersLog(): void {
    this.service.get('marketing/transporter.php?type=getTransportersLog').subscribe(
      (response: any) => {
        this.results = Array.isArray(response) ? response : [];
      },
      () => {
        this.results = [];
      }
    );
  }

  view(index: number): void {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
}
