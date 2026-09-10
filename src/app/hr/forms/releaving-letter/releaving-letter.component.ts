import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-releaving-letter',
  templateUrl: './releaving-letter.component.html',
  styleUrls: ['./releaving-letter.component.css']
})
export class ReleavingLetterComponent implements OnInit {
  resignations;
  isShow = false;
  selectedResignation;
  id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingResignationLetters();
  }

  getPendingResignationLetters() {
    this.service.get('hrDepartment.php?type=getPendingResignationLetters')
    .subscribe(response => {
      this.resignations = response;
    });
  }

  showLetter(index) {
    index = index-1;
    this.selectedResignation = this.resignations[index];
    this.id = this.selectedResignation['id'];
    this.isShow = true;
  }

  downloadPdf() {
    window.open(this.service.url+'pdf/releavingletter.php?id='+this.id +'&token='+localStorage.getItem('token')+'&type=Get Releaving Letter','_blank');
  }

  print() {
    window.open(this.service.url+'print/releavingletter.php?id='+this.id +'&token='+localStorage.getItem('token')+'&type=Get Releaving Letter','_blank');
  }

}
