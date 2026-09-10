import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-audit',
  templateUrl: './audit.component.html',
  styleUrls: ['./audit.component.css']
})
export class AuditComponent implements OnInit {
  audit_type='';
  constructor(private service: DataAccessService,private router: Router ) {}


  ngOnInit(): void {
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('it/itall.php?type=saveauditform',JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }



}
