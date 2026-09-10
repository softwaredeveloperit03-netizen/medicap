import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CleaningschdComponent } from './cleaningschd.component';

describe('CleaningschdComponent', () => {
  let component: CleaningschdComponent;
  let fixture: ComponentFixture<CleaningschdComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CleaningschdComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CleaningschdComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
