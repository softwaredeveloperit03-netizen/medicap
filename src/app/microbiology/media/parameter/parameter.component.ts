import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
declare let alertify;
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-parameter',
  templateUrl: './parameter.component.html',
  styleUrls: ['./parameter.component.css']
})
export class ParameterComponent implements OnInit {

  standards;
  isNew=false;
  selectedResult=[];
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getmedia();
  }

  getmedia() {
    this.service.get('master/media.php?type=getMedia').subscribe(response => {
      this.standards = response;
    });
  }
  
  download() {
    this.service.open('master/media.php?type=downloadMediaParameter')
  }
  edit(index){
    this.selectedResult=this.standards[index];
    this.isNew=true;
  }
  update(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/media.php?type=updateMedia&id='+this.selectedResult['id'], JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isNew=false;
        this.getmedia();
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }

}
