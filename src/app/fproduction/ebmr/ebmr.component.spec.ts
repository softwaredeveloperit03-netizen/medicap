import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EbmrComponent } from './ebmr.component';

describe('EbmrComponent', () => {
  let component: EbmrComponent;
  let fixture: ComponentFixture<EbmrComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EbmrComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EbmrComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
