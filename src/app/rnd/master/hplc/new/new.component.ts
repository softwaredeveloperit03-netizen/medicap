import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
import{Router} from '@angular/router';
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  result;

  constructor(private service: DataAccessService,private router : Router) { }

  ngOnInit() {
    
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qc/hplc.php?type=saveHPLC',JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        data.reset();
        this.router.navigate(['/master/hpcl/log'])
        alertify.success('Record Inserted Successfully');
      } else if (response['status'] === 'failed') {
        alertify.error('Failed To Occured Page,Try Again!');
        data.reset();
      }
    });
  }

}
