import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HypoComponent } from './hypo.component';

describe('HypoComponent', () => {
  let component: HypoComponent;
  let fixture: ComponentFixture<HypoComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HypoComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HypoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
