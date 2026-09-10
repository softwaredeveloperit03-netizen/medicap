import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ServerRoomTempMonitComponent } from './server-room-temp-monit.component';

describe('ServerRoomTempMonitComponent', () => {
  let component: ServerRoomTempMonitComponent;
  let fixture: ComponentFixture<ServerRoomTempMonitComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ServerRoomTempMonitComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ServerRoomTempMonitComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
