import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-stock1',
  templateUrl: './stock1.component.html',
  styleUrls: ['./stock1.component.css'],
  providers: [DatePipe]
})
export class Stock1Component implements OnInit {

  from_date = '';
  to_date = '';
  results;
  labours;
  balances;
  medias;
  selectedResults=[];
  isDispose=false;
  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getMedia();
    this.getMediaMaster();
  }
  
  getMediaMaster(){
    this.service.get('master/media.php?type=getMedia').subscribe(response =>{
      this.medias = response;
    });
  }

  getMedia(){
    this.service.get('microbiology/media.php?type=getMediaStock&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('microbiology/media.php?type=downloadMediaStock&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  decontamination(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/media.php?type=saveDecontaminationRequest',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getMedia();
        this.isDispose = false;
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  dispose(index){
    this.selectedResults=this.results[index];
    this.isDispose = true;
  }
}
