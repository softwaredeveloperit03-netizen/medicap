import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BmrApprvalComponent } from './bmr-apprval.component';

describe('BmrApprvalComponent', () => {
  let component: BmrApprvalComponent;
  let fixture: ComponentFixture<BmrApprvalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BmrApprvalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BmrApprvalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
