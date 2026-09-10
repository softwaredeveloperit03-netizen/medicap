import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QareviewComponent } from './qareview.component';

describe('QareviewComponent', () => {
  let component: QareviewComponent;
  let fixture: ComponentFixture<QareviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QareviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QareviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
