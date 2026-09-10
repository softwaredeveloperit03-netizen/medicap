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

  ngOnInit() {
  }

  saveVolumetricMaster(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qc/volumetric.php?type=saveVolumetricMaster', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.router.navigate(['/master/volumetric/log'])
        alertify.success('Volumetric Solution Saved Successfully');
      }
    });
  }

}
