import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VerifConfigComponent } from './verif-config.component';

describe('VerifConfigComponent', () => {
  let component: VerifConfigComponent;
  let fixture: ComponentFixture<VerifConfigComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VerifConfigComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VerifConfigComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
