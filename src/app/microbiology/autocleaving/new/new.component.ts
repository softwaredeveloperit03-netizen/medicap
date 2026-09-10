import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isNew=false;
  labours;
  medias;
  lafs;
  balances;
  equipments;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getAutoclaves();
    this.getMediaMaster();
  }
  getMediaMaster() {
    this.service.get('master/media.php?type=getMedia').subscribe(response => {
      this.medias = response;
    });
  }

  getAutoclaves() {
    this.service.get('equipments.php?type=getAutoclaves').subscribe(response => {
      this.equipments = response;
     });
  }

  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/autoclave.php?type=saveAutoclave',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.isNew=false;
        this.router.navigate(['/microbiology/autocleave/sterilization']);
        this.getAutoclaves();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }

}
