import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LeadbyComponent } from './leadby.component';

describe('LeadbyComponent', () => {
  let component: LeadbyComponent;
  let fixture: ComponentFixture<LeadbyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LeadbyComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LeadbyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
