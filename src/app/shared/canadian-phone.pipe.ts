import { Pipe, PipeTransform } from '@angular/core';
import { formatCanadianPhone } from './validators/canadian-phone.validator';

@Pipe({ name: 'canadianPhone' })
export class CanadianPhonePipe implements PipeTransform {
  transform(value: unknown): string {
    return formatCanadianPhone(value);
  }
}
