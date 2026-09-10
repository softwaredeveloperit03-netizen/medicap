import { ComponentFixture, TestBed } from '@angular/core/testing';

import { KfComponent } from './kf.component';

describe('KfComponent', () => {
  let component: KfComponent;
  let fixture: ComponentFixture<KfComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ KfComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(KfComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
