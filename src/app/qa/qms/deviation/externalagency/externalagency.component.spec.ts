import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExternalagencyComponent } from './externalagency.component';

describe('ExternalagencyComponent', () => {
  let component: ExternalagencyComponent;
  let fixture: ComponentFixture<ExternalagencyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExternalagencyComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExternalagencyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
