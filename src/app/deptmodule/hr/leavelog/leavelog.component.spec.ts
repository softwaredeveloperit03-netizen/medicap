import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LeavelogComponent } from './leavelog.component';

describe('LeavelogComponent', () => {
  let component: LeavelogComponent;
  let fixture: ComponentFixture<LeavelogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LeavelogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LeavelogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
