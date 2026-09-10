import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  result;

  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('rnd/qc/master/hplc.php?type=saveHPLC',JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        data.reset();
        alertify.success('Record Inserted Successfully');
        this.router.navigate(['/qc/master/hplc']);
      } else if (response['status'] === 'failed') {
        alertify.error('Failed To Occured Page,Try Again!');
        data.reset();
      }
    });
  }
}
