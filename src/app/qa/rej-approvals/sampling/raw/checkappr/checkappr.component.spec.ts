import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CheckapprComponent } from './checkappr.component';

describe('CheckapprComponent', () => {
  let component: CheckapprComponent;
  let fixture: ComponentFixture<CheckapprComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CheckapprComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CheckapprComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
