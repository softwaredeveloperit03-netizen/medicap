import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InprocheckingComponent } from './inprochecking.component';

describe('InprocheckingComponent', () => {
  let component: InprocheckingComponent;
  let fixture: ComponentFixture<InprocheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InprocheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InprocheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
