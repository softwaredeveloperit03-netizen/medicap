import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BmrlogComponent } from './bmrlog.component';

describe('BmrlogComponent', () => {
  let component: BmrlogComponent;
  let fixture: ComponentFixture<BmrlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BmrlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BmrlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
