import { Component, OnInit } from '@angular/core';
import { SupportAccessService } from '../support-access.service';
declare let alertify;


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {

  lists;

  constructor(public service: SupportAccessService) {}

  ngOnInit(): void {
    this.getUsersLog();
  }
  
  getUsersLog(){
    this.service.get('support.php?type=getQueryByRaisedById').subscribe(response=>{
      this.lists = response;
    });
  }

  acnkoDataByName = [];
  discription = '' ;
  isDiscription = false;
  viewDiscription(viewDiscription){
    this.discription = '';
    this.isDiscription = true;
    this.discription = viewDiscription;
  }


  acnkoData = [];
  isAkno = false;

  viewAck(acnkoData){
    this.acnkoData  = [];
    this.isAkno = true;
    this.acnkoData = acnkoData;
    this.getAcknowdgingChat(this.acnkoData['support_ticket_no']);
  }

  newMessage = '';
    
  sendMessage(){
    if (this.newMessage.trim()) {
      let temp = {};
      temp['support_ticket_no'] = this.acnkoData['support_ticket_no'];
      temp['chatFrom'] = 'RAISER';
      temp['msg'] = this.newMessage;
      temp['senderId'] =  localStorage.getItem('emp_id');

      this.service.post('support.php?type=sendTicketChatSms',JSON.stringify(temp)).subscribe(response =>{
        if(response['status']=='success') {
           this.getAcknowdgingChat(this.acnkoData['support_ticket_no']);
          this.newMessage = '';
          this.isAkno = false;
          setTimeout(() => this.scrollToBottom(), 100);
        } else{
          alertify.error(response['msg']);
        }
      });
    
    }else{
      alertify.error("Please Add Message....");
    }
  }



  scrollToBottom() {
    const container = document.querySelector('.chat-messages');
    if (container) container.scrollTop = container.scrollHeight;
  }


  AcknowdgingChat;
  getAcknowdgingChat(support_ticket_no){
    this.service.get('support.php?type=getAcknowdgingChat&support_ticket_no='+support_ticket_no).subscribe(response=>{
      this.AcknowdgingChat = response;
    });
  }


  
  
  viewfile(url) {
     url = this.service.url + 'upload/supportImage/' + url;
    window.open(url, '_blank');
  }

  loading: boolean = false;

 
  searchQuery: string = '';

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.lists;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.lists.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }



  plantColors: { [plantId: string]: string } = {};
  colorPalette = ['#e6f7ff',   '#f9f0ff', '#fffbe6',  '#f6ffed',   '#fff0f6',   '#f0f5ff',   '#e6fffb', '#fcffe6',   '#fff7e6', '#f0fff4',   '#e6f4ff',   '#fef0f0',   '#f0f9ff',   '#fffbf0', '#f3f0ff',  '#fdf6ec',  '#f0ffe0',  '#e8f5e9',  '#fce4ec',   '#f3e5f5'   ];
  private colorIndex = 0;

  getPlantColor(plantId: string): string {
    if (!this.plantColors[plantId]) {
      this.plantColors[plantId] = this.colorPalette[this.colorIndex % this.colorPalette.length];
      this.colorIndex++;
    }
    return this.plantColors[plantId];
  }










}
