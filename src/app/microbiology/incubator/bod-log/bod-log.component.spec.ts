import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BodLogComponent } from './bod-log.component';

describe('BodLogComponent', () => {
  let component: BodLogComponent;
  let fixture: ComponentFixture<BodLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BodLogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BodLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
