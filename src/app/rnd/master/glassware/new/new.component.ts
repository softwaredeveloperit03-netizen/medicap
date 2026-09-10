import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  
constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {

  }

  saveGlassware(data) {
    if(!data.valid) {
      alertify.error('All Fiedls are Required..');
      return;
    }
    this.service.post('qc/glassware.php?type=saveGlassware', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted successfully');
        data.resetForm();
        this.router.navigate(['/master/glassware/log']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
