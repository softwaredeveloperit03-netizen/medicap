import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CommitteeAttendenceComponent } from './committee-attendence.component';

describe('CommitteeAttendenceComponent', () => {
  let component: CommitteeAttendenceComponent;
  let fixture: ComponentFixture<CommitteeAttendenceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CommitteeAttendenceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CommitteeAttendenceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
