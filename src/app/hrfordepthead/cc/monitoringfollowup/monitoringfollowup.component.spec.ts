import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MonitoringfollowupComponent } from './monitoringfollowup.component';

describe('MonitoringfollowupComponent', () => {
  let component: MonitoringfollowupComponent;
  let fixture: ComponentFixture<MonitoringfollowupComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MonitoringfollowupComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MonitoringfollowupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
